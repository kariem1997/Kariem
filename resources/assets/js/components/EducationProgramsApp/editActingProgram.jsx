import * as React from "react";
import EducationProgramService from "../../services/EducationProgramService";
import update from 'immutability-helper';
import {EntityTypes, EntityCreator} from "./EntityCreator";
import EntityListEntry from "./EntityListEntry";

export default class EditActingProgram extends React.Component {

    constructor(props) {
        super(props);

        this.state = {loading: false, ep_name: '', competence: [], timeslot: [], resource_person:[]};

        this.autoUpdaterTimeout = null;

        this.programOnNameChange = this.programOnNameChange.bind(this);
        this.removeFromStateArray = this.removeFromStateArray.bind(this);
        this.onEntityCreated = this.onEntityCreated.bind(this);
        this.onEntityUpdatedName = this.onEntityUpdatedName.bind(this);
    }

    componentDidMount() {
        this.setState({loading: true});
        EducationProgramService.getEditableEducationProgram(response => {
            this.setState(response.data);
            this.setState({loading: false});

        }, this.props.id);
    }

    // Update component when the ID changes, fired by parent
    componentWillReceiveProps(nextProps) {
        if (nextProps.id !== this.props.id) {
            this.setState({loading: true});

            EducationProgramService.getEditableEducationProgram(response => {
                this.setState(response.data);
                this.setState({loading: false});
            }, nextProps.id);
        }
    }

    // On education program name change
    programOnNameChange(element) {
        clearTimeout(this.autoUpdaterTimeout);
        this.setState({
            [element.target.getAttribute('name')]: element.target.value
        });

        this.autoUpdaterTimeout = setTimeout(() => {
            EducationProgramService.updateName(this.props.id, {ep_name: this.state.ep_name}, response => {
                this.props.programOnNameChange(this.props.id, response.data.program.ep_name);
            })
        }, 500)



    }

    // On removing competence/timeslot/resourceperson from list
    removeFromStateArray(id, type) {

        // Magic
        EducationProgramService.deleteEntity(EntityTypes[type], id, response => {
            const index = this.getEntityIndex(id, type);
            this.setState(prevState => ({
                [type]: update(prevState[type], {$splice: [[index, 1]]})
            }));
        })
    }

    getEntityIndex(id, type) {
        return this.state[type].findIndex(entity => entity[type + '_id'] === parseInt(id));
    }

    // Update the entity in the parent array
    onEntityUpdatedName(id, type, name, mappedNameField) {
        const index = this.getEntityIndex(id, type);
        this.setState(prevState => ({
            [type]: update(prevState[type], {[index]: {[mappedNameField]: {$set: name}}})
        }));
    }

    // When the user adds a new entity
    onEntityCreated(type, entity) {
        if (type === EntityTypes.competence) {
            this.setState(prevState => ({competence: update(prevState.competence, {$push: [entity]})}))
        } else if (type === EntityTypes.timeslot) {
            this.setState(prevState => ({timeslot: update(prevState.timeslot, {$push: [entity]})}))
        } else if(type === EntityTypes.resourcePerson) {
            this.setState(prevState => ({resource_person: update(prevState.resource_person, {$push: [entity]})}))
        }
    }

    render() {
        if (this.state.loading) return <div className="loader">Loading...</div>;
        const program = this.state;
        return <div>
            <div>
                <h4>Program details</h4>
                <div className="form-group">
                    <label>
                        Education program name
                        <input type="text" className="form-control" name="ep_name" value={program.ep_name}
                               onChange={this.programOnNameChange}/>
                    </label>
                </div>
            </div>

            <div>
                <h4>Competencies</h4>
                <div className="form-group">
                    <ul>
                        {program.competence.map(competence => {
                            return <li key={competence.competence_id}>
                                <EntityListEntry type="competence"
                                                 id={competence.competence_id}
                                                 label={competence.competence_label}
                                                 onRemoveClick={this.removeFromStateArray}
                                                 onEntityUpdatedName={this.onEntityUpdatedName}
                                />
                            </li>
                        })}
                    </ul>
                    <EntityCreator onEntityCreated={this.onEntityCreated} type={EntityTypes.competence}
                                   programId={this.props.id}/>

                </div>
            </div>

            <div>
                <h4>Timeslots</h4>
                <div className="form-group">
                    <ul>
                        {program.timeslot.map(timeslot => {
                            return <li key={timeslot.timeslot_id}>
                                <EntityListEntry type="timeslot"
                                                 id={timeslot.timeslot_id}
                                                 label={timeslot.timeslot_text}
                                                 onRemoveClick={this.removeFromStateArray}
                                                 onEntityUpdatedName={this.onEntityUpdatedName}

                                />
                            </li>
                        })}
                    </ul>
                    <EntityCreator onEntityCreated={this.onEntityCreated} type={EntityTypes.timeslot}
                                   programId={this.props.id}/>

                </div>
            </div>

            <div>
                <h4>Resource Persons</h4>
                <div className="form-group">
                    <ul>
                        {program.resource_person.map(resourcePerson => {
                            return <li key={resourcePerson.rp_id}>
                                <EntityListEntry type="resource_person"
                                                 id={resourcePerson.rp_id}
                                                 label={resourcePerson.person_label}
                                                 onRemoveClick={this.removeFromStateArray}
                                                 onEntityUpdatedName={this.onEntityUpdatedName}

                                />

                            </li>
                        })}
                    </ul>
                    <EntityCreator onEntityCreated={this.onEntityCreated} type={EntityTypes.resourcePerson}
                                   programId={this.props.id}/>

                </div>
            </div>
        </div>;
    }


}